<?php

namespace App\Form;

use App\Entity\LogBookTest;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookTestType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (array_key_exists('search', $options) && $options['search'] === true) {
            $this->buildSearchForm($builder, $options);
        } else {
            $builder
                ->add('name')
                ->add('timeStart')
                ->add('timeEnd')
                ->add('timeRun')
                ->add('dutUpTimeStart')
                ->add('dutUpTimeEnd')
                ->add('verdict')
                ->add('executionOrder')
                ->add('cycle')
                ->add('disabled')
            ;
            $name = $builder->getName();
        }

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildSearchForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class,
                array(
                    'required' => false,
                    'attr' =>
                        array(
                            'style' => 'width:400px;'
                        )
                ))
            //->add('timeStart')
            //->add('timeEnd')
            //->add('timeRun')
            ->add('verdict', LogBookSelectableVerdictType::class, array('required' => false))
            ->add('setup', LogBookSelectableSetupType::class, array('required' => false))
            //->add('executionOrder')
            ->add('cycle')
            //->add('disabled')
        ;
        //                'placeholder' => 'Choose a verdict',
////                'compound' => true,
//                'multiple'  => true,
////                'label'     => 'Are you agree?',
//                'attr'      => array('class' => 'well')
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            'search' => false,
//            'compound' => true,
            'data_class' => LogBookTest::class,
        ]);
    }
}
